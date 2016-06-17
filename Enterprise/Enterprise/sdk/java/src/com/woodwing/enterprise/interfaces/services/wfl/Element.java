/**
 * Element.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class Element  implements java.io.Serializable {
    private java.lang.String ID;

    private java.lang.String name;

    private org.apache.axis.types.UnsignedInt lengthWords;

    private org.apache.axis.types.UnsignedInt lengthChars;

    private org.apache.axis.types.UnsignedInt lengthParas;

    private org.apache.axis.types.UnsignedInt lengthLines;

    private java.lang.String snippet;

    private java.lang.String version;

    private java.lang.String content;

    public Element() {
    }

    public Element(
           java.lang.String ID,
           java.lang.String name,
           org.apache.axis.types.UnsignedInt lengthWords,
           org.apache.axis.types.UnsignedInt lengthChars,
           org.apache.axis.types.UnsignedInt lengthParas,
           org.apache.axis.types.UnsignedInt lengthLines,
           java.lang.String snippet,
           java.lang.String version,
           java.lang.String content) {
           this.ID = ID;
           this.name = name;
           this.lengthWords = lengthWords;
           this.lengthChars = lengthChars;
           this.lengthParas = lengthParas;
           this.lengthLines = lengthLines;
           this.snippet = snippet;
           this.version = version;
           this.content = content;
    }


    /**
     * Gets the ID value for this Element.
     * 
     * @return ID
     */
    public java.lang.String getID() {
        return ID;
    }


    /**
     * Sets the ID value for this Element.
     * 
     * @param ID
     */
    public void setID(java.lang.String ID) {
        this.ID = ID;
    }


    /**
     * Gets the name value for this Element.
     * 
     * @return name
     */
    public java.lang.String getName() {
        return name;
    }


    /**
     * Sets the name value for this Element.
     * 
     * @param name
     */
    public void setName(java.lang.String name) {
        this.name = name;
    }


    /**
     * Gets the lengthWords value for this Element.
     * 
     * @return lengthWords
     */
    public org.apache.axis.types.UnsignedInt getLengthWords() {
        return lengthWords;
    }


    /**
     * Sets the lengthWords value for this Element.
     * 
     * @param lengthWords
     */
    public void setLengthWords(org.apache.axis.types.UnsignedInt lengthWords) {
        this.lengthWords = lengthWords;
    }


    /**
     * Gets the lengthChars value for this Element.
     * 
     * @return lengthChars
     */
    public org.apache.axis.types.UnsignedInt getLengthChars() {
        return lengthChars;
    }


    /**
     * Sets the lengthChars value for this Element.
     * 
     * @param lengthChars
     */
    public void setLengthChars(org.apache.axis.types.UnsignedInt lengthChars) {
        this.lengthChars = lengthChars;
    }


    /**
     * Gets the lengthParas value for this Element.
     * 
     * @return lengthParas
     */
    public org.apache.axis.types.UnsignedInt getLengthParas() {
        return lengthParas;
    }


    /**
     * Sets the lengthParas value for this Element.
     * 
     * @param lengthParas
     */
    public void setLengthParas(org.apache.axis.types.UnsignedInt lengthParas) {
        this.lengthParas = lengthParas;
    }


    /**
     * Gets the lengthLines value for this Element.
     * 
     * @return lengthLines
     */
    public org.apache.axis.types.UnsignedInt getLengthLines() {
        return lengthLines;
    }


    /**
     * Sets the lengthLines value for this Element.
     * 
     * @param lengthLines
     */
    public void setLengthLines(org.apache.axis.types.UnsignedInt lengthLines) {
        this.lengthLines = lengthLines;
    }


    /**
     * Gets the snippet value for this Element.
     * 
     * @return snippet
     */
    public java.lang.String getSnippet() {
        return snippet;
    }


    /**
     * Sets the snippet value for this Element.
     * 
     * @param snippet
     */
    public void setSnippet(java.lang.String snippet) {
        this.snippet = snippet;
    }


    /**
     * Gets the version value for this Element.
     * 
     * @return version
     */
    public java.lang.String getVersion() {
        return version;
    }


    /**
     * Sets the version value for this Element.
     * 
     * @param version
     */
    public void setVersion(java.lang.String version) {
        this.version = version;
    }


    /**
     * Gets the content value for this Element.
     * 
     * @return content
     */
    public java.lang.String getContent() {
        return content;
    }


    /**
     * Sets the content value for this Element.
     * 
     * @param content
     */
    public void setContent(java.lang.String content) {
        this.content = content;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof Element)) return false;
        Element other = (Element) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.ID==null && other.getID()==null) || 
             (this.ID!=null &&
              this.ID.equals(other.getID()))) &&
            ((this.name==null && other.getName()==null) || 
             (this.name!=null &&
              this.name.equals(other.getName()))) &&
            ((this.lengthWords==null && other.getLengthWords()==null) || 
             (this.lengthWords!=null &&
              this.lengthWords.equals(other.getLengthWords()))) &&
            ((this.lengthChars==null && other.getLengthChars()==null) || 
             (this.lengthChars!=null &&
              this.lengthChars.equals(other.getLengthChars()))) &&
            ((this.lengthParas==null && other.getLengthParas()==null) || 
             (this.lengthParas!=null &&
              this.lengthParas.equals(other.getLengthParas()))) &&
            ((this.lengthLines==null && other.getLengthLines()==null) || 
             (this.lengthLines!=null &&
              this.lengthLines.equals(other.getLengthLines()))) &&
            ((this.snippet==null && other.getSnippet()==null) || 
             (this.snippet!=null &&
              this.snippet.equals(other.getSnippet()))) &&
            ((this.version==null && other.getVersion()==null) || 
             (this.version!=null &&
              this.version.equals(other.getVersion()))) &&
            ((this.content==null && other.getContent()==null) || 
             (this.content!=null &&
              this.content.equals(other.getContent())));
        __equalsCalc = null;
        return _equals;
    }

    private boolean __hashCodeCalc = false;
    public synchronized int hashCode() {
        if (__hashCodeCalc) {
            return 0;
        }
        __hashCodeCalc = true;
        int _hashCode = 1;
        if (getID() != null) {
            _hashCode += getID().hashCode();
        }
        if (getName() != null) {
            _hashCode += getName().hashCode();
        }
        if (getLengthWords() != null) {
            _hashCode += getLengthWords().hashCode();
        }
        if (getLengthChars() != null) {
            _hashCode += getLengthChars().hashCode();
        }
        if (getLengthParas() != null) {
            _hashCode += getLengthParas().hashCode();
        }
        if (getLengthLines() != null) {
            _hashCode += getLengthLines().hashCode();
        }
        if (getSnippet() != null) {
            _hashCode += getSnippet().hashCode();
        }
        if (getVersion() != null) {
            _hashCode += getVersion().hashCode();
        }
        if (getContent() != null) {
            _hashCode += getContent().hashCode();
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(Element.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Element"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("ID");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ID"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("name");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Name"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("lengthWords");
        elemField.setXmlName(new javax.xml.namespace.QName("", "LengthWords"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "unsignedInt"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("lengthChars");
        elemField.setXmlName(new javax.xml.namespace.QName("", "LengthChars"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "unsignedInt"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("lengthParas");
        elemField.setXmlName(new javax.xml.namespace.QName("", "LengthParas"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "unsignedInt"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("lengthLines");
        elemField.setXmlName(new javax.xml.namespace.QName("", "LengthLines"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "unsignedInt"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("snippet");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Snippet"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("version");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Version"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("content");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Content"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
    }

    /**
     * Return type metadata object
     */
    public static org.apache.axis.description.TypeDesc getTypeDesc() {
        return typeDesc;
    }

    /**
     * Get Custom Serializer
     */
    public static org.apache.axis.encoding.Serializer getSerializer(
           java.lang.String mechType, 
           java.lang.Class _javaType,  
           javax.xml.namespace.QName _xmlType) {
        return 
          new  org.apache.axis.encoding.ser.BeanSerializer(
            _javaType, _xmlType, typeDesc);
    }

    /**
     * Get Custom Deserializer
     */
    public static org.apache.axis.encoding.Deserializer getDeserializer(
           java.lang.String mechType, 
           java.lang.Class _javaType,  
           javax.xml.namespace.QName _xmlType) {
        return 
          new  org.apache.axis.encoding.ser.BeanDeserializer(
            _javaType, _xmlType, typeDesc);
    }

}
