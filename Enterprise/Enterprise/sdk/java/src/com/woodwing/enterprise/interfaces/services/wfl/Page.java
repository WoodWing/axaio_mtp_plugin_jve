/**
 * Page.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class Page  implements java.io.Serializable {
    private double width;

    private double height;

    private java.lang.String pageNumber;

    private org.apache.axis.types.UnsignedInt pageOrder;

    private com.woodwing.enterprise.interfaces.services.wfl.Attachment[] files;

    private com.woodwing.enterprise.interfaces.services.wfl.Edition edition;

    private java.lang.String master;

    private com.woodwing.enterprise.interfaces.services.wfl.InstanceType instance;

    private org.apache.axis.types.UnsignedInt pageSequence;

    private com.woodwing.enterprise.interfaces.services.wfl.RenditionType[] renditions;

    private java.lang.String orientation;

    public Page() {
    }

    public Page(
           double width,
           double height,
           java.lang.String pageNumber,
           org.apache.axis.types.UnsignedInt pageOrder,
           com.woodwing.enterprise.interfaces.services.wfl.Attachment[] files,
           com.woodwing.enterprise.interfaces.services.wfl.Edition edition,
           java.lang.String master,
           com.woodwing.enterprise.interfaces.services.wfl.InstanceType instance,
           org.apache.axis.types.UnsignedInt pageSequence,
           com.woodwing.enterprise.interfaces.services.wfl.RenditionType[] renditions,
           java.lang.String orientation) {
           this.width = width;
           this.height = height;
           this.pageNumber = pageNumber;
           this.pageOrder = pageOrder;
           this.files = files;
           this.edition = edition;
           this.master = master;
           this.instance = instance;
           this.pageSequence = pageSequence;
           this.renditions = renditions;
           this.orientation = orientation;
    }


    /**
     * Gets the width value for this Page.
     * 
     * @return width
     */
    public double getWidth() {
        return width;
    }


    /**
     * Sets the width value for this Page.
     * 
     * @param width
     */
    public void setWidth(double width) {
        this.width = width;
    }


    /**
     * Gets the height value for this Page.
     * 
     * @return height
     */
    public double getHeight() {
        return height;
    }


    /**
     * Sets the height value for this Page.
     * 
     * @param height
     */
    public void setHeight(double height) {
        this.height = height;
    }


    /**
     * Gets the pageNumber value for this Page.
     * 
     * @return pageNumber
     */
    public java.lang.String getPageNumber() {
        return pageNumber;
    }


    /**
     * Sets the pageNumber value for this Page.
     * 
     * @param pageNumber
     */
    public void setPageNumber(java.lang.String pageNumber) {
        this.pageNumber = pageNumber;
    }


    /**
     * Gets the pageOrder value for this Page.
     * 
     * @return pageOrder
     */
    public org.apache.axis.types.UnsignedInt getPageOrder() {
        return pageOrder;
    }


    /**
     * Sets the pageOrder value for this Page.
     * 
     * @param pageOrder
     */
    public void setPageOrder(org.apache.axis.types.UnsignedInt pageOrder) {
        this.pageOrder = pageOrder;
    }


    /**
     * Gets the files value for this Page.
     * 
     * @return files
     */
    public com.woodwing.enterprise.interfaces.services.wfl.Attachment[] getFiles() {
        return files;
    }


    /**
     * Sets the files value for this Page.
     * 
     * @param files
     */
    public void setFiles(com.woodwing.enterprise.interfaces.services.wfl.Attachment[] files) {
        this.files = files;
    }


    /**
     * Gets the edition value for this Page.
     * 
     * @return edition
     */
    public com.woodwing.enterprise.interfaces.services.wfl.Edition getEdition() {
        return edition;
    }


    /**
     * Sets the edition value for this Page.
     * 
     * @param edition
     */
    public void setEdition(com.woodwing.enterprise.interfaces.services.wfl.Edition edition) {
        this.edition = edition;
    }


    /**
     * Gets the master value for this Page.
     * 
     * @return master
     */
    public java.lang.String getMaster() {
        return master;
    }


    /**
     * Sets the master value for this Page.
     * 
     * @param master
     */
    public void setMaster(java.lang.String master) {
        this.master = master;
    }


    /**
     * Gets the instance value for this Page.
     * 
     * @return instance
     */
    public com.woodwing.enterprise.interfaces.services.wfl.InstanceType getInstance() {
        return instance;
    }


    /**
     * Sets the instance value for this Page.
     * 
     * @param instance
     */
    public void setInstance(com.woodwing.enterprise.interfaces.services.wfl.InstanceType instance) {
        this.instance = instance;
    }


    /**
     * Gets the pageSequence value for this Page.
     * 
     * @return pageSequence
     */
    public org.apache.axis.types.UnsignedInt getPageSequence() {
        return pageSequence;
    }


    /**
     * Sets the pageSequence value for this Page.
     * 
     * @param pageSequence
     */
    public void setPageSequence(org.apache.axis.types.UnsignedInt pageSequence) {
        this.pageSequence = pageSequence;
    }


    /**
     * Gets the renditions value for this Page.
     * 
     * @return renditions
     */
    public com.woodwing.enterprise.interfaces.services.wfl.RenditionType[] getRenditions() {
        return renditions;
    }


    /**
     * Sets the renditions value for this Page.
     * 
     * @param renditions
     */
    public void setRenditions(com.woodwing.enterprise.interfaces.services.wfl.RenditionType[] renditions) {
        this.renditions = renditions;
    }


    /**
     * Gets the orientation value for this Page.
     * 
     * @return orientation
     */
    public java.lang.String getOrientation() {
        return orientation;
    }


    /**
     * Sets the orientation value for this Page.
     * 
     * @param orientation
     */
    public void setOrientation(java.lang.String orientation) {
        this.orientation = orientation;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof Page)) return false;
        Page other = (Page) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            this.width == other.getWidth() &&
            this.height == other.getHeight() &&
            ((this.pageNumber==null && other.getPageNumber()==null) || 
             (this.pageNumber!=null &&
              this.pageNumber.equals(other.getPageNumber()))) &&
            ((this.pageOrder==null && other.getPageOrder()==null) || 
             (this.pageOrder!=null &&
              this.pageOrder.equals(other.getPageOrder()))) &&
            ((this.files==null && other.getFiles()==null) || 
             (this.files!=null &&
              java.util.Arrays.equals(this.files, other.getFiles()))) &&
            ((this.edition==null && other.getEdition()==null) || 
             (this.edition!=null &&
              this.edition.equals(other.getEdition()))) &&
            ((this.master==null && other.getMaster()==null) || 
             (this.master!=null &&
              this.master.equals(other.getMaster()))) &&
            ((this.instance==null && other.getInstance()==null) || 
             (this.instance!=null &&
              this.instance.equals(other.getInstance()))) &&
            ((this.pageSequence==null && other.getPageSequence()==null) || 
             (this.pageSequence!=null &&
              this.pageSequence.equals(other.getPageSequence()))) &&
            ((this.renditions==null && other.getRenditions()==null) || 
             (this.renditions!=null &&
              java.util.Arrays.equals(this.renditions, other.getRenditions()))) &&
            ((this.orientation==null && other.getOrientation()==null) || 
             (this.orientation!=null &&
              this.orientation.equals(other.getOrientation())));
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
        _hashCode += new Double(getWidth()).hashCode();
        _hashCode += new Double(getHeight()).hashCode();
        if (getPageNumber() != null) {
            _hashCode += getPageNumber().hashCode();
        }
        if (getPageOrder() != null) {
            _hashCode += getPageOrder().hashCode();
        }
        if (getFiles() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getFiles());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getFiles(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getEdition() != null) {
            _hashCode += getEdition().hashCode();
        }
        if (getMaster() != null) {
            _hashCode += getMaster().hashCode();
        }
        if (getInstance() != null) {
            _hashCode += getInstance().hashCode();
        }
        if (getPageSequence() != null) {
            _hashCode += getPageSequence().hashCode();
        }
        if (getRenditions() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getRenditions());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getRenditions(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getOrientation() != null) {
            _hashCode += getOrientation().hashCode();
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(Page.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Page"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("width");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Width"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "double"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("height");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Height"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "double"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("pageNumber");
        elemField.setXmlName(new javax.xml.namespace.QName("", "PageNumber"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("pageOrder");
        elemField.setXmlName(new javax.xml.namespace.QName("", "PageOrder"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "unsignedInt"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("files");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Files"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Attachment"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Attachment"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("edition");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Edition"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Edition"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("master");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Master"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("instance");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Instance"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "InstanceType"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("pageSequence");
        elemField.setXmlName(new javax.xml.namespace.QName("", "PageSequence"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "unsignedInt"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("renditions");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Renditions"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "RenditionType"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "RenditionType"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("orientation");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Orientation"));
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
