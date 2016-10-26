/**
 * ContentMetaData.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class ContentMetaData  implements java.io.Serializable {
    private java.lang.String description;

    private java.lang.String descriptionAuthor;

    private java.lang.String[] keywords;

    private java.lang.String slugline;

    private java.lang.String format;

    private java.lang.Integer columns;

    private java.lang.Double width;

    private java.lang.Double height;

    private java.lang.Double dpi;

    private org.apache.axis.types.UnsignedInt lengthWords;

    private org.apache.axis.types.UnsignedInt lengthChars;

    private org.apache.axis.types.UnsignedInt lengthParas;

    private org.apache.axis.types.UnsignedInt lengthLines;

    private java.lang.String plainContent;

    private org.apache.axis.types.UnsignedInt fileSize;

    private java.lang.String colorSpace;

    private java.lang.String highResFile;

    private java.lang.String encoding;

    private java.lang.String compression;

    private org.apache.axis.types.UnsignedInt keyFrameEveryFrames;

    private java.lang.String channels;

    private java.lang.String aspectRatio;

    private org.apache.axis.types.UnsignedInt orientation;

    private java.lang.String dimensions;

    public ContentMetaData() {
    }

    public ContentMetaData(
           java.lang.String description,
           java.lang.String descriptionAuthor,
           java.lang.String[] keywords,
           java.lang.String slugline,
           java.lang.String format,
           java.lang.Integer columns,
           java.lang.Double width,
           java.lang.Double height,
           java.lang.Double dpi,
           org.apache.axis.types.UnsignedInt lengthWords,
           org.apache.axis.types.UnsignedInt lengthChars,
           org.apache.axis.types.UnsignedInt lengthParas,
           org.apache.axis.types.UnsignedInt lengthLines,
           java.lang.String plainContent,
           org.apache.axis.types.UnsignedInt fileSize,
           java.lang.String colorSpace,
           java.lang.String highResFile,
           java.lang.String encoding,
           java.lang.String compression,
           org.apache.axis.types.UnsignedInt keyFrameEveryFrames,
           java.lang.String channels,
           java.lang.String aspectRatio,
           org.apache.axis.types.UnsignedInt orientation,
           java.lang.String dimensions) {
           this.description = description;
           this.descriptionAuthor = descriptionAuthor;
           this.keywords = keywords;
           this.slugline = slugline;
           this.format = format;
           this.columns = columns;
           this.width = width;
           this.height = height;
           this.dpi = dpi;
           this.lengthWords = lengthWords;
           this.lengthChars = lengthChars;
           this.lengthParas = lengthParas;
           this.lengthLines = lengthLines;
           this.plainContent = plainContent;
           this.fileSize = fileSize;
           this.colorSpace = colorSpace;
           this.highResFile = highResFile;
           this.encoding = encoding;
           this.compression = compression;
           this.keyFrameEveryFrames = keyFrameEveryFrames;
           this.channels = channels;
           this.aspectRatio = aspectRatio;
           this.orientation = orientation;
           this.dimensions = dimensions;
    }


    /**
     * Gets the description value for this ContentMetaData.
     * 
     * @return description
     */
    public java.lang.String getDescription() {
        return description;
    }


    /**
     * Sets the description value for this ContentMetaData.
     * 
     * @param description
     */
    public void setDescription(java.lang.String description) {
        this.description = description;
    }


    /**
     * Gets the descriptionAuthor value for this ContentMetaData.
     * 
     * @return descriptionAuthor
     */
    public java.lang.String getDescriptionAuthor() {
        return descriptionAuthor;
    }


    /**
     * Sets the descriptionAuthor value for this ContentMetaData.
     * 
     * @param descriptionAuthor
     */
    public void setDescriptionAuthor(java.lang.String descriptionAuthor) {
        this.descriptionAuthor = descriptionAuthor;
    }


    /**
     * Gets the keywords value for this ContentMetaData.
     * 
     * @return keywords
     */
    public java.lang.String[] getKeywords() {
        return keywords;
    }


    /**
     * Sets the keywords value for this ContentMetaData.
     * 
     * @param keywords
     */
    public void setKeywords(java.lang.String[] keywords) {
        this.keywords = keywords;
    }


    /**
     * Gets the slugline value for this ContentMetaData.
     * 
     * @return slugline
     */
    public java.lang.String getSlugline() {
        return slugline;
    }


    /**
     * Sets the slugline value for this ContentMetaData.
     * 
     * @param slugline
     */
    public void setSlugline(java.lang.String slugline) {
        this.slugline = slugline;
    }


    /**
     * Gets the format value for this ContentMetaData.
     * 
     * @return format
     */
    public java.lang.String getFormat() {
        return format;
    }


    /**
     * Sets the format value for this ContentMetaData.
     * 
     * @param format
     */
    public void setFormat(java.lang.String format) {
        this.format = format;
    }


    /**
     * Gets the columns value for this ContentMetaData.
     * 
     * @return columns
     */
    public java.lang.Integer getColumns() {
        return columns;
    }


    /**
     * Sets the columns value for this ContentMetaData.
     * 
     * @param columns
     */
    public void setColumns(java.lang.Integer columns) {
        this.columns = columns;
    }


    /**
     * Gets the width value for this ContentMetaData.
     * 
     * @return width
     */
    public java.lang.Double getWidth() {
        return width;
    }


    /**
     * Sets the width value for this ContentMetaData.
     * 
     * @param width
     */
    public void setWidth(java.lang.Double width) {
        this.width = width;
    }


    /**
     * Gets the height value for this ContentMetaData.
     * 
     * @return height
     */
    public java.lang.Double getHeight() {
        return height;
    }


    /**
     * Sets the height value for this ContentMetaData.
     * 
     * @param height
     */
    public void setHeight(java.lang.Double height) {
        this.height = height;
    }


    /**
     * Gets the dpi value for this ContentMetaData.
     * 
     * @return dpi
     */
    public java.lang.Double getDpi() {
        return dpi;
    }


    /**
     * Sets the dpi value for this ContentMetaData.
     * 
     * @param dpi
     */
    public void setDpi(java.lang.Double dpi) {
        this.dpi = dpi;
    }


    /**
     * Gets the lengthWords value for this ContentMetaData.
     * 
     * @return lengthWords
     */
    public org.apache.axis.types.UnsignedInt getLengthWords() {
        return lengthWords;
    }


    /**
     * Sets the lengthWords value for this ContentMetaData.
     * 
     * @param lengthWords
     */
    public void setLengthWords(org.apache.axis.types.UnsignedInt lengthWords) {
        this.lengthWords = lengthWords;
    }


    /**
     * Gets the lengthChars value for this ContentMetaData.
     * 
     * @return lengthChars
     */
    public org.apache.axis.types.UnsignedInt getLengthChars() {
        return lengthChars;
    }


    /**
     * Sets the lengthChars value for this ContentMetaData.
     * 
     * @param lengthChars
     */
    public void setLengthChars(org.apache.axis.types.UnsignedInt lengthChars) {
        this.lengthChars = lengthChars;
    }


    /**
     * Gets the lengthParas value for this ContentMetaData.
     * 
     * @return lengthParas
     */
    public org.apache.axis.types.UnsignedInt getLengthParas() {
        return lengthParas;
    }


    /**
     * Sets the lengthParas value for this ContentMetaData.
     * 
     * @param lengthParas
     */
    public void setLengthParas(org.apache.axis.types.UnsignedInt lengthParas) {
        this.lengthParas = lengthParas;
    }


    /**
     * Gets the lengthLines value for this ContentMetaData.
     * 
     * @return lengthLines
     */
    public org.apache.axis.types.UnsignedInt getLengthLines() {
        return lengthLines;
    }


    /**
     * Sets the lengthLines value for this ContentMetaData.
     * 
     * @param lengthLines
     */
    public void setLengthLines(org.apache.axis.types.UnsignedInt lengthLines) {
        this.lengthLines = lengthLines;
    }


    /**
     * Gets the plainContent value for this ContentMetaData.
     * 
     * @return plainContent
     */
    public java.lang.String getPlainContent() {
        return plainContent;
    }


    /**
     * Sets the plainContent value for this ContentMetaData.
     * 
     * @param plainContent
     */
    public void setPlainContent(java.lang.String plainContent) {
        this.plainContent = plainContent;
    }


    /**
     * Gets the fileSize value for this ContentMetaData.
     * 
     * @return fileSize
     */
    public org.apache.axis.types.UnsignedInt getFileSize() {
        return fileSize;
    }


    /**
     * Sets the fileSize value for this ContentMetaData.
     * 
     * @param fileSize
     */
    public void setFileSize(org.apache.axis.types.UnsignedInt fileSize) {
        this.fileSize = fileSize;
    }


    /**
     * Gets the colorSpace value for this ContentMetaData.
     * 
     * @return colorSpace
     */
    public java.lang.String getColorSpace() {
        return colorSpace;
    }


    /**
     * Sets the colorSpace value for this ContentMetaData.
     * 
     * @param colorSpace
     */
    public void setColorSpace(java.lang.String colorSpace) {
        this.colorSpace = colorSpace;
    }


    /**
     * Gets the highResFile value for this ContentMetaData.
     * 
     * @return highResFile
     */
    public java.lang.String getHighResFile() {
        return highResFile;
    }


    /**
     * Sets the highResFile value for this ContentMetaData.
     * 
     * @param highResFile
     */
    public void setHighResFile(java.lang.String highResFile) {
        this.highResFile = highResFile;
    }


    /**
     * Gets the encoding value for this ContentMetaData.
     * 
     * @return encoding
     */
    public java.lang.String getEncoding() {
        return encoding;
    }


    /**
     * Sets the encoding value for this ContentMetaData.
     * 
     * @param encoding
     */
    public void setEncoding(java.lang.String encoding) {
        this.encoding = encoding;
    }


    /**
     * Gets the compression value for this ContentMetaData.
     * 
     * @return compression
     */
    public java.lang.String getCompression() {
        return compression;
    }


    /**
     * Sets the compression value for this ContentMetaData.
     * 
     * @param compression
     */
    public void setCompression(java.lang.String compression) {
        this.compression = compression;
    }


    /**
     * Gets the keyFrameEveryFrames value for this ContentMetaData.
     * 
     * @return keyFrameEveryFrames
     */
    public org.apache.axis.types.UnsignedInt getKeyFrameEveryFrames() {
        return keyFrameEveryFrames;
    }


    /**
     * Sets the keyFrameEveryFrames value for this ContentMetaData.
     * 
     * @param keyFrameEveryFrames
     */
    public void setKeyFrameEveryFrames(org.apache.axis.types.UnsignedInt keyFrameEveryFrames) {
        this.keyFrameEveryFrames = keyFrameEveryFrames;
    }


    /**
     * Gets the channels value for this ContentMetaData.
     * 
     * @return channels
     */
    public java.lang.String getChannels() {
        return channels;
    }


    /**
     * Sets the channels value for this ContentMetaData.
     * 
     * @param channels
     */
    public void setChannels(java.lang.String channels) {
        this.channels = channels;
    }


    /**
     * Gets the aspectRatio value for this ContentMetaData.
     * 
     * @return aspectRatio
     */
    public java.lang.String getAspectRatio() {
        return aspectRatio;
    }


    /**
     * Sets the aspectRatio value for this ContentMetaData.
     * 
     * @param aspectRatio
     */
    public void setAspectRatio(java.lang.String aspectRatio) {
        this.aspectRatio = aspectRatio;
    }


    /**
     * Gets the orientation value for this ContentMetaData.
     * 
     * @return orientation
     */
    public org.apache.axis.types.UnsignedInt getOrientation() {
        return orientation;
    }


    /**
     * Sets the orientation value for this ContentMetaData.
     * 
     * @param orientation
     */
    public void setOrientation(org.apache.axis.types.UnsignedInt orientation) {
        this.orientation = orientation;
    }


    /**
     * Gets the dimensions value for this ContentMetaData.
     * 
     * @return dimensions
     */
    public java.lang.String getDimensions() {
        return dimensions;
    }


    /**
     * Sets the dimensions value for this ContentMetaData.
     * 
     * @param dimensions
     */
    public void setDimensions(java.lang.String dimensions) {
        this.dimensions = dimensions;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof ContentMetaData)) return false;
        ContentMetaData other = (ContentMetaData) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.description==null && other.getDescription()==null) || 
             (this.description!=null &&
              this.description.equals(other.getDescription()))) &&
            ((this.descriptionAuthor==null && other.getDescriptionAuthor()==null) || 
             (this.descriptionAuthor!=null &&
              this.descriptionAuthor.equals(other.getDescriptionAuthor()))) &&
            ((this.keywords==null && other.getKeywords()==null) || 
             (this.keywords!=null &&
              java.util.Arrays.equals(this.keywords, other.getKeywords()))) &&
            ((this.slugline==null && other.getSlugline()==null) || 
             (this.slugline!=null &&
              this.slugline.equals(other.getSlugline()))) &&
            ((this.format==null && other.getFormat()==null) || 
             (this.format!=null &&
              this.format.equals(other.getFormat()))) &&
            ((this.columns==null && other.getColumns()==null) || 
             (this.columns!=null &&
              this.columns.equals(other.getColumns()))) &&
            ((this.width==null && other.getWidth()==null) || 
             (this.width!=null &&
              this.width.equals(other.getWidth()))) &&
            ((this.height==null && other.getHeight()==null) || 
             (this.height!=null &&
              this.height.equals(other.getHeight()))) &&
            ((this.dpi==null && other.getDpi()==null) || 
             (this.dpi!=null &&
              this.dpi.equals(other.getDpi()))) &&
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
            ((this.plainContent==null && other.getPlainContent()==null) || 
             (this.plainContent!=null &&
              this.plainContent.equals(other.getPlainContent()))) &&
            ((this.fileSize==null && other.getFileSize()==null) || 
             (this.fileSize!=null &&
              this.fileSize.equals(other.getFileSize()))) &&
            ((this.colorSpace==null && other.getColorSpace()==null) || 
             (this.colorSpace!=null &&
              this.colorSpace.equals(other.getColorSpace()))) &&
            ((this.highResFile==null && other.getHighResFile()==null) || 
             (this.highResFile!=null &&
              this.highResFile.equals(other.getHighResFile()))) &&
            ((this.encoding==null && other.getEncoding()==null) || 
             (this.encoding!=null &&
              this.encoding.equals(other.getEncoding()))) &&
            ((this.compression==null && other.getCompression()==null) || 
             (this.compression!=null &&
              this.compression.equals(other.getCompression()))) &&
            ((this.keyFrameEveryFrames==null && other.getKeyFrameEveryFrames()==null) || 
             (this.keyFrameEveryFrames!=null &&
              this.keyFrameEveryFrames.equals(other.getKeyFrameEveryFrames()))) &&
            ((this.channels==null && other.getChannels()==null) || 
             (this.channels!=null &&
              this.channels.equals(other.getChannels()))) &&
            ((this.aspectRatio==null && other.getAspectRatio()==null) || 
             (this.aspectRatio!=null &&
              this.aspectRatio.equals(other.getAspectRatio()))) &&
            ((this.orientation==null && other.getOrientation()==null) || 
             (this.orientation!=null &&
              this.orientation.equals(other.getOrientation()))) &&
            ((this.dimensions==null && other.getDimensions()==null) || 
             (this.dimensions!=null &&
              this.dimensions.equals(other.getDimensions())));
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
        if (getDescription() != null) {
            _hashCode += getDescription().hashCode();
        }
        if (getDescriptionAuthor() != null) {
            _hashCode += getDescriptionAuthor().hashCode();
        }
        if (getKeywords() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getKeywords());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getKeywords(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getSlugline() != null) {
            _hashCode += getSlugline().hashCode();
        }
        if (getFormat() != null) {
            _hashCode += getFormat().hashCode();
        }
        if (getColumns() != null) {
            _hashCode += getColumns().hashCode();
        }
        if (getWidth() != null) {
            _hashCode += getWidth().hashCode();
        }
        if (getHeight() != null) {
            _hashCode += getHeight().hashCode();
        }
        if (getDpi() != null) {
            _hashCode += getDpi().hashCode();
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
        if (getPlainContent() != null) {
            _hashCode += getPlainContent().hashCode();
        }
        if (getFileSize() != null) {
            _hashCode += getFileSize().hashCode();
        }
        if (getColorSpace() != null) {
            _hashCode += getColorSpace().hashCode();
        }
        if (getHighResFile() != null) {
            _hashCode += getHighResFile().hashCode();
        }
        if (getEncoding() != null) {
            _hashCode += getEncoding().hashCode();
        }
        if (getCompression() != null) {
            _hashCode += getCompression().hashCode();
        }
        if (getKeyFrameEveryFrames() != null) {
            _hashCode += getKeyFrameEveryFrames().hashCode();
        }
        if (getChannels() != null) {
            _hashCode += getChannels().hashCode();
        }
        if (getAspectRatio() != null) {
            _hashCode += getAspectRatio().hashCode();
        }
        if (getOrientation() != null) {
            _hashCode += getOrientation().hashCode();
        }
        if (getDimensions() != null) {
            _hashCode += getDimensions().hashCode();
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(ContentMetaData.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "ContentMetaData"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("description");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Description"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("descriptionAuthor");
        elemField.setXmlName(new javax.xml.namespace.QName("", "DescriptionAuthor"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("keywords");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Keywords"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "String"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "String"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("slugline");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Slugline"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("format");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Format"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("columns");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Columns"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "int"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("width");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Width"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "double"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("height");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Height"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "double"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("dpi");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Dpi"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "double"));
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
        elemField.setFieldName("plainContent");
        elemField.setXmlName(new javax.xml.namespace.QName("", "PlainContent"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("fileSize");
        elemField.setXmlName(new javax.xml.namespace.QName("", "FileSize"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "unsignedInt"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("colorSpace");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ColorSpace"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("highResFile");
        elemField.setXmlName(new javax.xml.namespace.QName("", "HighResFile"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("encoding");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Encoding"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("compression");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Compression"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("keyFrameEveryFrames");
        elemField.setXmlName(new javax.xml.namespace.QName("", "KeyFrameEveryFrames"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "unsignedInt"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("channels");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Channels"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("aspectRatio");
        elemField.setXmlName(new javax.xml.namespace.QName("", "AspectRatio"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("orientation");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Orientation"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "unsignedInt"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("dimensions");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Dimensions"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setMinOccurs(0);
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
