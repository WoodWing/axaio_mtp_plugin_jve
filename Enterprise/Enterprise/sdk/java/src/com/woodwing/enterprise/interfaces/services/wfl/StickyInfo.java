/**
 * StickyInfo.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class StickyInfo  implements java.io.Serializable {
    private java.lang.Double anchorX;

    private java.lang.Double anchorY;

    private java.lang.Double left;

    private java.lang.Double top;

    private java.lang.Double width;

    private java.lang.Double height;

    private org.apache.axis.types.UnsignedInt page;

    private java.lang.String version;

    private java.lang.String color;

    private org.apache.axis.types.UnsignedInt pageSequence;

    public StickyInfo() {
    }

    public StickyInfo(
           java.lang.Double anchorX,
           java.lang.Double anchorY,
           java.lang.Double left,
           java.lang.Double top,
           java.lang.Double width,
           java.lang.Double height,
           org.apache.axis.types.UnsignedInt page,
           java.lang.String version,
           java.lang.String color,
           org.apache.axis.types.UnsignedInt pageSequence) {
           this.anchorX = anchorX;
           this.anchorY = anchorY;
           this.left = left;
           this.top = top;
           this.width = width;
           this.height = height;
           this.page = page;
           this.version = version;
           this.color = color;
           this.pageSequence = pageSequence;
    }


    /**
     * Gets the anchorX value for this StickyInfo.
     * 
     * @return anchorX
     */
    public java.lang.Double getAnchorX() {
        return anchorX;
    }


    /**
     * Sets the anchorX value for this StickyInfo.
     * 
     * @param anchorX
     */
    public void setAnchorX(java.lang.Double anchorX) {
        this.anchorX = anchorX;
    }


    /**
     * Gets the anchorY value for this StickyInfo.
     * 
     * @return anchorY
     */
    public java.lang.Double getAnchorY() {
        return anchorY;
    }


    /**
     * Sets the anchorY value for this StickyInfo.
     * 
     * @param anchorY
     */
    public void setAnchorY(java.lang.Double anchorY) {
        this.anchorY = anchorY;
    }


    /**
     * Gets the left value for this StickyInfo.
     * 
     * @return left
     */
    public java.lang.Double getLeft() {
        return left;
    }


    /**
     * Sets the left value for this StickyInfo.
     * 
     * @param left
     */
    public void setLeft(java.lang.Double left) {
        this.left = left;
    }


    /**
     * Gets the top value for this StickyInfo.
     * 
     * @return top
     */
    public java.lang.Double getTop() {
        return top;
    }


    /**
     * Sets the top value for this StickyInfo.
     * 
     * @param top
     */
    public void setTop(java.lang.Double top) {
        this.top = top;
    }


    /**
     * Gets the width value for this StickyInfo.
     * 
     * @return width
     */
    public java.lang.Double getWidth() {
        return width;
    }


    /**
     * Sets the width value for this StickyInfo.
     * 
     * @param width
     */
    public void setWidth(java.lang.Double width) {
        this.width = width;
    }


    /**
     * Gets the height value for this StickyInfo.
     * 
     * @return height
     */
    public java.lang.Double getHeight() {
        return height;
    }


    /**
     * Sets the height value for this StickyInfo.
     * 
     * @param height
     */
    public void setHeight(java.lang.Double height) {
        this.height = height;
    }


    /**
     * Gets the page value for this StickyInfo.
     * 
     * @return page
     */
    public org.apache.axis.types.UnsignedInt getPage() {
        return page;
    }


    /**
     * Sets the page value for this StickyInfo.
     * 
     * @param page
     */
    public void setPage(org.apache.axis.types.UnsignedInt page) {
        this.page = page;
    }


    /**
     * Gets the version value for this StickyInfo.
     * 
     * @return version
     */
    public java.lang.String getVersion() {
        return version;
    }


    /**
     * Sets the version value for this StickyInfo.
     * 
     * @param version
     */
    public void setVersion(java.lang.String version) {
        this.version = version;
    }


    /**
     * Gets the color value for this StickyInfo.
     * 
     * @return color
     */
    public java.lang.String getColor() {
        return color;
    }


    /**
     * Sets the color value for this StickyInfo.
     * 
     * @param color
     */
    public void setColor(java.lang.String color) {
        this.color = color;
    }


    /**
     * Gets the pageSequence value for this StickyInfo.
     * 
     * @return pageSequence
     */
    public org.apache.axis.types.UnsignedInt getPageSequence() {
        return pageSequence;
    }


    /**
     * Sets the pageSequence value for this StickyInfo.
     * 
     * @param pageSequence
     */
    public void setPageSequence(org.apache.axis.types.UnsignedInt pageSequence) {
        this.pageSequence = pageSequence;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof StickyInfo)) return false;
        StickyInfo other = (StickyInfo) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.anchorX==null && other.getAnchorX()==null) || 
             (this.anchorX!=null &&
              this.anchorX.equals(other.getAnchorX()))) &&
            ((this.anchorY==null && other.getAnchorY()==null) || 
             (this.anchorY!=null &&
              this.anchorY.equals(other.getAnchorY()))) &&
            ((this.left==null && other.getLeft()==null) || 
             (this.left!=null &&
              this.left.equals(other.getLeft()))) &&
            ((this.top==null && other.getTop()==null) || 
             (this.top!=null &&
              this.top.equals(other.getTop()))) &&
            ((this.width==null && other.getWidth()==null) || 
             (this.width!=null &&
              this.width.equals(other.getWidth()))) &&
            ((this.height==null && other.getHeight()==null) || 
             (this.height!=null &&
              this.height.equals(other.getHeight()))) &&
            ((this.page==null && other.getPage()==null) || 
             (this.page!=null &&
              this.page.equals(other.getPage()))) &&
            ((this.version==null && other.getVersion()==null) || 
             (this.version!=null &&
              this.version.equals(other.getVersion()))) &&
            ((this.color==null && other.getColor()==null) || 
             (this.color!=null &&
              this.color.equals(other.getColor()))) &&
            ((this.pageSequence==null && other.getPageSequence()==null) || 
             (this.pageSequence!=null &&
              this.pageSequence.equals(other.getPageSequence())));
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
        if (getAnchorX() != null) {
            _hashCode += getAnchorX().hashCode();
        }
        if (getAnchorY() != null) {
            _hashCode += getAnchorY().hashCode();
        }
        if (getLeft() != null) {
            _hashCode += getLeft().hashCode();
        }
        if (getTop() != null) {
            _hashCode += getTop().hashCode();
        }
        if (getWidth() != null) {
            _hashCode += getWidth().hashCode();
        }
        if (getHeight() != null) {
            _hashCode += getHeight().hashCode();
        }
        if (getPage() != null) {
            _hashCode += getPage().hashCode();
        }
        if (getVersion() != null) {
            _hashCode += getVersion().hashCode();
        }
        if (getColor() != null) {
            _hashCode += getColor().hashCode();
        }
        if (getPageSequence() != null) {
            _hashCode += getPageSequence().hashCode();
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(StickyInfo.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "StickyInfo"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("anchorX");
        elemField.setXmlName(new javax.xml.namespace.QName("", "AnchorX"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "double"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("anchorY");
        elemField.setXmlName(new javax.xml.namespace.QName("", "AnchorY"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "double"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("left");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Left"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "double"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("top");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Top"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "double"));
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
        elemField.setFieldName("page");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Page"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "unsignedInt"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("version");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Version"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("color");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Color"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("pageSequence");
        elemField.setXmlName(new javax.xml.namespace.QName("", "PageSequence"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "unsignedInt"));
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
