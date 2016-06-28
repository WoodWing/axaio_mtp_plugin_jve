<?xml version="1.0" encoding="utf-8" ?>
<!-- This stylesheet converts XML into hightlighted HTML, which indents and line endings -->
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:output method="xml" indent="no" />
	
	<xsl:template match="/"> <!-- root node -->
		<xsl:apply-templates select="node()"/>
	</xsl:template>

	<xsl:template match="*"> <!-- nodes -->
		<xsl:choose>
			<xsl:when test="not(./node())"> <!-- empty nodes -->
				<div style="padding-left: 25px;">
					<span style="color: blue;">&lt;</span>
					<span style="color: purple; font-weight: bold;"><xsl:value-of select="name()"/></span>
					<xsl:apply-templates select="@*"/>
					<span style="color: blue;">/&gt;</span>
				</div>
				<xsl:apply-templates />
			</xsl:when>
			<xsl:otherwise> <!-- nodes with children -->
				<div style="padding-left: 25px;">
					<span style="color: #3300FF;">&lt;</span>
					<span style="color: purple; font-weight: bold;"><xsl:value-of select="name()"/></span>
					<xsl:apply-templates select="@*"/>
					<span style="color: #3300FF;">&gt;</span>
					<xsl:apply-templates select="node()"/>
					<span style="color: blue;">&lt;/</span>
					<span style="color: purple; font-weight: bold;"><xsl:value-of select="name()"/></span>
					<span style="color: blue;">&gt;</span>
				</div>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<xsl:template match="@*"> <!-- attributes -->
		<span style="color: black; font-weight: bold;"><xsl:text> </xsl:text><xsl:value-of select="name()"/></span>
		<span style="color: blue;">="</span>
		<span style="color: blue; "><xsl:value-of select="."/></span>
		<span style="color: blue;">"</span>
	</xsl:template>

	<xsl:template match="text()"> <!-- text elements -->
		<span style="color: black;"><xsl:value-of select="."/></span>
	</xsl:template>

</xsl:stylesheet>